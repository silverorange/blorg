create or replace view BlorgPostVisibleCommentCountView as
	select BlorgPost.id as post, count(BlorgComment.id) as comment_count,
			max(BlorgComment.createdate) as last_comment_date
		from BlorgPost
			left outer join BlorgComment on
				BlorgComment.post = BlorgPost.id and
				BlorgComment.spam = false and
				BlorgComment.status = 1 -- status 1 is published
		group by BlorgPost.id;
