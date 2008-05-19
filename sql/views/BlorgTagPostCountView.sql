create or replace view BlorgTagPostCountView as
	select BlorgTag.id as tag, count(BlorgPost.id) as post_count,
			max(BlorgPost.publish_date) as last_post_date
		from BlorgTag
			left outer join BlorgPostTagBinding
				on BlorgTag.id = BlorgPostTagBinding.tag
			left outer join BlorgPost
				on BlorgPostTagBinding.post = BlorgPost.id
		where BlorgPost.id is null or (BlorgPost.enabled = true and
			BlorgPost.publish_date <= CURRENT_TIMESTAMP)
		group by BlorgTag.id;
